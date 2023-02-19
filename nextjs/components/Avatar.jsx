import { useEffect, useState, useMemo } from "react";
import { createAvatar } from "@dicebear/core";
import Image from "next/image";
import { botttsNeutral } from "@dicebear/collection";

export default function Avatar() {
  const avatar = useMemo(() => {
    return createAvatar(botttsNeutral, {
      size: 128,
      // ... other options
    }).toDataUriSync();
  });

  return <Image src={avatar} width={40} height={40} alt="Avatar" />;
}
